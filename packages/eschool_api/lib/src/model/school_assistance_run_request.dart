//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'school_assistance_run_request.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SchoolAssistanceRunRequest {
  /// Returns a new [SchoolAssistanceRunRequest] instance.
  SchoolAssistanceRunRequest({required this.identifiersReviewed});

  /// Must be true: review exact text for visible names and identifiers before sharing.
  @JsonKey(name: r'identifiers_reviewed', required: true, includeIfNull: false)
  final bool identifiersReviewed;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SchoolAssistanceRunRequest &&
          other.identifiersReviewed == identifiersReviewed;

  @override
  int get hashCode => identifiersReviewed.hashCode;

  factory SchoolAssistanceRunRequest.fromJson(Map<String, dynamic> json) =>
      _$SchoolAssistanceRunRequestFromJson(json);

  Map<String, dynamic> toJson() => _$SchoolAssistanceRunRequestToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
