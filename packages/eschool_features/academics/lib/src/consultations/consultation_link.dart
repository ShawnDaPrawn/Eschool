Object? consultationLinkArguments(
  Map<String, String> values,
  Map<String, String> query,
) {
  final raw = values['consultation'] ?? query['consultation'];
  if (raw == null) return null;
  final id = RegExp(r'^[1-9][0-9]*$').hasMatch(raw) ? int.tryParse(raw) : null;
  if (id == null || id <= 0) {
    throw const FormatException(
      'A consultation link needs a positive identifier.',
    );
  }
  return id;
}
