//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'note_bursary_application_request.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class NoteBursaryApplicationRequest {
  /// Returns a new [NoteBursaryApplicationRequest] instance.
  NoteBursaryApplicationRequest({
    required this.operationId,

    required this.reasons,
  });

  /// Retry the same request with the same value.
  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @JsonKey(name: r'reasons', required: true, includeIfNull: false)
  final String reasons;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is NoteBursaryApplicationRequest &&
          other.operationId == operationId &&
          other.reasons == reasons;

  @override
  int get hashCode => operationId.hashCode + reasons.hashCode;

  factory NoteBursaryApplicationRequest.fromJson(Map<String, dynamic> json) =>
      _$NoteBursaryApplicationRequestFromJson(json);

  Map<String, dynamic> toJson() => _$NoteBursaryApplicationRequestToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
