/// Makes a value that came from the server safe to use as a single file name
/// inside a directory: path separators, control characters and characters that
/// are not allowed in file names are replaced, '..' sequences and leading or
/// trailing dots and spaces are removed, so the file cannot end up outside
/// the directory it is saved to. Returns [fallback] when nothing usable is left.
String sanitizeFileName(String value, {String fallback = 'file'}) {
  String sanitized = value.replaceAll(
    RegExp(r'[/\\:*?"<>|\x00-\x1F\x7F]'),
    '_',
  );
  while (sanitized.contains('..')) {
    sanitized = sanitized.replaceAll('..', '.');
  }
  sanitized = sanitized.replaceAll(RegExp(r'^[.\s]+|[.\s]+$'), '');
  return sanitized.isEmpty ? fallback : sanitized;
}
