/// Exact hundredth-unit parsing for field validation. Weighted marks are server-owned.
int? markUnits(String value, int maximum) {
  final text = value.trim().replaceFirst(',', '.');
  if (text.isEmpty) return null;
  if (!RegExp(r'^\d{1,7}(?:\.\d{1,2})?$').hasMatch(text)) {
    throw const FormatException(
      'Use a number with at most two decimal places.',
    );
  }
  final parts = text.split('.');
  final units =
      int.parse(parts.first) * 100 +
      (parts.length == 1 ? 0 : int.parse(parts.last.padRight(2, '0')));
  if (units > maximum) {
    throw const FormatException('The score exceeds the maximum.');
  }
  return units;
}

String markDecimal(int units) =>
    '${units ~/ 100}.${(units % 100).toString().padLeft(2, '0')}';

/// A bounded rectangular Excel TSV. Parse everything before changing any draft.
List<List<String>> markPaste(
  String text, {
  int maxRows = 200,
  int maxColumns = 100,
}) {
  if (text.length > 256 * 1024) {
    throw const FormatException('The pasted selection is too large.');
  }
  final normal = text.replaceAll('\r\n', '\n').replaceAll('\r', '\n');
  final lines = normal.split('\n');
  if (lines.isNotEmpty && lines.last.isEmpty) lines.removeLast();
  if (lines.isEmpty || lines.length > maxRows) {
    throw const FormatException('Choose a smaller rectangular selection.');
  }
  final rows = lines
      .map((line) => line.split('\t').map((v) => v.trim()).toList())
      .toList();
  final columns = rows.first.length;
  if (columns > maxColumns || rows.any((row) => row.length != columns)) {
    throw const FormatException(
      'Paste a rectangular selection without headings.',
    );
  }
  return rows;
}
