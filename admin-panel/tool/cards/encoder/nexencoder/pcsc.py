"""Small PC/SC ctypes adapter; no tracing, provisioning or code on import.

Linux pcsc-lite and Windows winscard ABIs are supported in source. Physical
reader/driver and macOS qualification remain required before deployment.
"""

import ctypes as C
import ctypes.util
import sys

from .ev2 import EncoderError, native_apdu


class PCSC:
    def __init__(self, reader=None):
        self.context = self.handle = None
        self.library = None
        try:
            windows = sys.platform == "win32"
            if sys.platform == "darwin":
                raise EncoderError("macOS PC/SC ABI is not qualified by this runtime.")
            self.word = C.c_uint32 if windows else C.c_ulong
            self.long = C.c_int32 if windows else C.c_long
            self.pointer = C.c_size_t if windows else C.c_long
            path = "winscard.dll" if windows else ctypes.util.find_library("pcsclite")
            if not path:
                raise EncoderError("A local PC/SC library is required.")
            # Restrict the Windows system library lookup; never load a DLL from
            # the downloaded bundle/current working directory.
            self.library = C.WinDLL(path, winmode=0x00000800) if windows else C.CDLL(path)
            suffix = "A" if windows else ""
            self.establish = self._bind("SCardEstablishContext", [self.word, C.c_void_p, C.c_void_p, C.POINTER(self.pointer)])
            self.release = self._bind("SCardReleaseContext", [self.pointer])
            self.list_readers = self._bind("SCardListReaders" + suffix, [self.pointer, C.c_void_p, C.c_void_p, C.POINTER(self.word)])
            self.connect = self._bind("SCardConnect" + suffix, [self.pointer, C.c_char_p, self.word, self.word, C.POINTER(self.pointer), C.POINTER(self.word)])
            self.reconnect = self._bind("SCardReconnect", [self.pointer, self.word, self.word, self.word, C.POINTER(self.word)])
            self.disconnect = self._bind("SCardDisconnect", [self.pointer, self.word])
            self.send = self._bind("SCardTransmit", [self.pointer, C.c_void_p, C.c_void_p, self.word, C.c_void_p, C.c_void_p, C.POINTER(self.word)])
            self.context = self.pointer()
            self._ok(self.establish(0, None, None, C.byref(self.context)))
            size = self.word()
            self._ok(self.list_readers(self.context, None, None, C.byref(size)))
            if not 2 <= size.value <= 65536:
                raise EncoderError("No bounded local PC/SC reader list is available.")
            names = C.create_string_buffer(size.value)
            self._ok(self.list_readers(self.context, None, names, C.byref(size)))
            choices = names.raw.rstrip(b"\0").split(b"\0")
            selected = reader.encode("utf-8") if reader else (choices[0] if len(choices) == 1 else None)
            if selected not in choices:
                raise EncoderError("Select one installed PC/SC reader explicitly.")
            self.handle = self.pointer()
            self.protocol = self.word()
            # Exclusive ownership prevents another application interleaving APDUs.
            self._ok(self.connect(self.context, selected, 1, 3, C.byref(self.handle), C.byref(self.protocol)))
            self.reset()
        except BaseException:
            self.close()
            raise

    def _bind(self, name, arguments):
        method = getattr(self.library, name)
        method.argtypes, method.restype = arguments, self.long
        return method

    @staticmethod
    def _ok(code):
        if code:
            raise EncoderError("Local PC/SC operation failed; reconnect and reauthorize.")

    def reset(self):
        self._ok(self.reconnect(self.handle, 1, 3, 1, C.byref(self.protocol)))
        # Select NFC Forum Type 4 application before native EV2 commands.
        if self.transmit(bytes.fromhex("00A4040C07D276000085010100")) != b"\x90\x00":
            raise EncoderError("The selected chip is not the required NFC application.")
        self.check_version()

    def check_version(self):
        # AN12196 table12: exactly three frames, never an unbounded AF loop.
        chunks = []
        for instruction, length, status in ((0x60, 7, b"\x91\xaf"),
                                             (0xAF, 7, b"\x91\xaf"),
                                             (0xAF, 14, b"\x91\x00")):
            answer = self.transmit(native_apdu(instruction, b""))
            if len(answer) != length + 2 or answer[-2:] != status:
                raise EncoderError("Unsupported or incomplete chip version; no authentication or write permitted.")
            chunks.append(answer[:-2])
        hardware, software, _serial_batch = chunks
        if (hardware[:2] != b"\x04\x04" or hardware[2] not in (2, 8)
                or hardware[3:] != bytes.fromhex("30001105")
                or software[:4] != bytes.fromhex("04040201")
                or software[4] not in (0, 1) or software[5:] != bytes.fromhex("1105")):
            raise EncoderError("Only the supported ordinary NTAG 424 DNA family may be personalized.")

    def transmit(self, apdu):
        if not isinstance(apdu, bytes) or not 4 <= len(apdu) <= 261:
            raise EncoderError("Invalid bounded APDU.")
        class IORequest(C.Structure):
            _fields_ = [("protocol", self.word), ("length", self.word)]
        request = IORequest(self.protocol.value, C.sizeof(IORequest))
        sent = C.create_string_buffer(apdu)
        answer = C.create_string_buffer(1024)
        size = self.word(len(answer))
        self._ok(self.send(self.handle, C.byref(request), sent, len(apdu), None, answer, C.byref(size)))
        if size.value > len(answer):
            raise EncoderError("PC/SC response exceeds its bound.")
        return answer.raw[:size.value]

    def read_ndef(self):
        if self.transmit(bytes.fromhex("00A4000C02E104")) != b"\x90\x00":
            raise EncoderError("NDEF file selection refused.")
        # Le=0 is exactly256; separate reads could contain different SDM counters.
        answer = self.transmit(bytes.fromhex("00B0000000"))
        if len(answer) != 258 or answer[-2:] != b"\x90\x00":
            raise EncoderError("A single coherent NDEF read failed.")
        return answer[:-2]

    def close(self):
        if self.library is not None and self.handle is not None and self.handle.value:
            try:
                self.disconnect(self.handle, 1)
            finally:
                self.handle = None
        if self.library is not None and self.context is not None and self.context.value:
            self.release(self.context)
            self.context = None

    def __enter__(self):
        return self

    def __exit__(self, *_):
        self.close()
