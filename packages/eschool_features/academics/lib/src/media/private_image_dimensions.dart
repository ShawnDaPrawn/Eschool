import 'dart:typed_data';

const privateWorkingMaximumBytes = 1048576;
const privateWorkingMaximumEncodedLength = 1398104;

/// Read PNG/JPEG dimensions before allocating decoded pixels on native or web.
({int width, int height}) privateImageDimensions(
  Uint8List bytes, {
  int maximumBytes = 4194304,
  int maximumEdge = 8192,
  int maximumPixels = 16777216,
}) {
  if (bytes.isEmpty || bytes.length > maximumBytes)
    throw const FormatException('image limit');
  int width = 0, height = 0;
  final data = ByteData.sublistView(bytes);
  const png = [137, 80, 78, 71, 13, 10, 26, 10];
  if (bytes.length >= 33 &&
      List.generate(8, (i) => bytes[i]).join(',') == png.join(',')) {
    if (data.getUint32(8) != 13 ||
        String.fromCharCodes(bytes.sublist(12, 16)) != 'IHDR')
      throw const FormatException('invalid PNG');
    width = data.getUint32(16);
    height = data.getUint32(20);
  } else if (bytes.length >= 4 && bytes[0] == 255 && bytes[1] == 216) {
    var offset = 2;
    while (offset + 3 < bytes.length) {
      if (bytes[offset++] != 255) throw const FormatException('invalid JPEG');
      while (offset < bytes.length && bytes[offset] == 255) {
        offset++;
      }
      if (offset >= bytes.length) break;
      final marker = bytes[offset++];
      if (marker == 217 || marker == 218 || offset + 2 > bytes.length) break;
      if (marker == 1 || (marker >= 208 && marker <= 215)) continue;
      final length = data.getUint16(offset);
      if (length < 2 || offset + length > bytes.length)
        throw const FormatException('invalid JPEG');
      if (const {
        192,
        193,
        194,
        195,
        197,
        198,
        199,
        201,
        202,
        203,
        205,
        206,
        207,
      }.contains(marker)) {
        if (length < 8) throw const FormatException('invalid JPEG');
        height = data.getUint16(offset + 3);
        width = data.getUint16(offset + 5);
        break;
      }
      offset += length;
    }
  }
  if (width < 1 ||
      height < 1 ||
      width > maximumEdge ||
      height > maximumEdge ||
      width * height > maximumPixels)
    throw const FormatException('image limit');
  return (width: width, height: height);
}
