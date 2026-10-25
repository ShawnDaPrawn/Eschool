// Browser transport fixture ONLY. This does not detect malware and must never
// be configured as a deployed scanner. Explicit opt-in and loopback by default.
if (process.env.ALLOW_SYNTHETIC_PHOTO_SCANNER !== '1') {
  throw new Error('Set ALLOW_SYNTHETIC_PHOTO_SCANNER=1 only for isolated browser tests.');
}
const net = require('node:net');
const server = net.createServer(socket => {
  let input = Buffer.alloc(0), streaming = false, count = 0;
  socket.setTimeout(5000, () => socket.destroy());
  socket.on('error', () => {});
  socket.on('data', chunk => {
    input = Buffer.concat([input, chunk]);
    if (input.length > 4194304) return socket.destroy();
    if (!streaming) {
      const end = input.indexOf(0);
      if (end < 0) return;
      const command = input.subarray(0, end).toString('ascii');
      input = input.subarray(end + 1);
      if (command === 'zVERSION') {
        return socket.end(`ClamAV 1.0-synthetic/1/${new Date().toISOString()}\0`);
      }
      if (command !== 'zINSTREAM') return socket.destroy();
      streaming = true;
    }
    while (input.length >= 4) {
      const size = input.readUInt32BE(0);
      if (size > 65536 || count + size > 4194304) return socket.destroy();
      if (input.length < 4 + size) return;
      input = input.subarray(4 + size);
      if (size === 0) return socket.end('stream: OK\0');
      count += size;
    }
  });
});
server.listen(Number(process.env.SYNTHETIC_PHOTO_SCANNER_PORT || 3316), process.env.SYNTHETIC_PHOTO_SCANNER_HOST || '127.0.0.1');
process.on('SIGTERM', () => server.close(() => process.exit(0)));
