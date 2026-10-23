//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'approval_state.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ApprovalState {
  /// Returns a new [ApprovalState] instance.
  ApprovalState({required this.status, required this.approvedByCount});

  @JsonKey(
    name: r'status',
    required: true,
    includeIfNull: false,
    unknownEnumValue: ApprovalStateStatusEnum.unknownDefaultOpenApi,
  )
  final ApprovalStateStatusEnum status;

  // minimum: 0
  @JsonKey(name: r'approved_by_count', required: true, includeIfNull: false)
  final int approvedByCount;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ApprovalState &&
          other.status == status &&
          other.approvedByCount == approvedByCount;

  @override
  int get hashCode => status.hashCode + approvedByCount.hashCode;

  factory ApprovalState.fromJson(Map<String, dynamic> json) =>
      _$ApprovalStateFromJson(json);

  Map<String, dynamic> toJson() => _$ApprovalStateToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum ApprovalStateStatusEnum {
  @JsonValue(r'pending')
  pending(r'pending'),
  @JsonValue(r'approved')
  approved(r'approved'),
  @JsonValue(r'withdrawn')
  withdrawn(r'withdrawn'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const ApprovalStateStatusEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
