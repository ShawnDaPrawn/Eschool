import 'package:equatable/equatable.dart';
import 'package:eschool_api/eschool_api.dart';

// The Phase 1 endpoints of this package (engineering/phases/phase-1.md,
// "Response shapes") are not in admin-panel/openapi.yaml yet. Their models
// are written by hand next to each repository, reusing the generated
// SchoolSummary; replace them with the generated eschool_api models and
// client once T2 and T3 add the endpoints to openapi.yaml.

/// A learner as the Phase 1 endpoints name them:
/// `{id, name, image, student_number, school: SchoolSummary}`.
class LearnerSummary extends Equatable {
  const LearnerSummary({
    required this.id,
    required this.name,
    this.image,
    this.studentNumber,
    this.school,
  });

  factory LearnerSummary.fromJson(Map<String, dynamic> json) => LearnerSummary(
    id: json['id'] is int
        ? json['id'] as int
        : int.tryParse('${json['id']}') ?? 0,
    name: '${json['name'] ?? ''}',
    image: readString(json['image']),
    studentNumber: readString(json['student_number']),
    school: readSchool(json['school']),
  );

  final int id;
  final String name;

  /// The avatar or photo URL; `null` when none.
  final String? image;
  final String? studentNumber;

  /// The learner's school; `null` when not sent.
  final SchoolSummary? school;

  @override
  List<Object?> get props => [id, name, image, studentNumber, school?.id];
}

/// A non-empty string, or `null`.
String? readString(Object? value) =>
    value is String && value.isNotEmpty ? value : null;

/// A `SchoolSummary` (generated), or `null` when missing or malformed.
SchoolSummary? readSchool(Object? value) {
  if (value is! Map) {
    return null;
  }
  try {
    return SchoolSummary.fromJson(Map<String, dynamic>.from(value));
  } catch (_) {
    return null;
  }
}

/// A date the server sends as `2026-10-22` or a full ISO 8601 time;
/// `null` when missing or not a date.
DateTime? readDate(Object? value) =>
    value is String ? DateTime.tryParse(value) : null;

/// The `data` of a v2 envelope as a list of objects.
List<Map<String, dynamic>> dataList(Map<String, dynamic> response) {
  final Object? data = response['data'];
  return [
    if (data is List)
      for (final Object? item in data)
        if (item is Map) Map<String, dynamic>.from(item),
  ];
}

/// The `data` of a v2 envelope as an object.
Map<String, dynamic> dataMap(Map<String, dynamic> response) {
  final Object? data = response['data'];
  return data is Map ? Map<String, dynamic>.from(data) : const {};
}
