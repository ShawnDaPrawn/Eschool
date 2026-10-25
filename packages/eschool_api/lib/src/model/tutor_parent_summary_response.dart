//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/tutor_parent_summary.dart';
import 'package:json_annotation/json_annotation.dart';

part 'tutor_parent_summary_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class TutorParentSummaryResponse {
  /// Returns a new [TutorParentSummaryResponse] instance.
  TutorParentSummaryResponse({
    required this.error,

    required this.message,

    required this.data,

    required this.code,
  });

  @JsonKey(name: r'error', required: true, includeIfNull: false)
  final bool error;

  @JsonKey(name: r'message', required: true, includeIfNull: false)
  final String message;

  @JsonKey(name: r'data', required: true, includeIfNull: false)
  final TutorParentSummary data;

  @JsonKey(name: r'code', required: true, includeIfNull: false)
  final int code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TutorParentSummaryResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + data.hashCode + code.hashCode;

  factory TutorParentSummaryResponse.fromJson(Map<String, dynamic> json) =>
      _$TutorParentSummaryResponseFromJson(json);

  Map<String, dynamic> toJson() => _$TutorParentSummaryResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
