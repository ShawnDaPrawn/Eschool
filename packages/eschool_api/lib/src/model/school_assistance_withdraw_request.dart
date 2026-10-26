//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'school_assistance_withdraw_request.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SchoolAssistanceWithdrawRequest {
  /// Returns a new [SchoolAssistanceWithdrawRequest] instance.
  SchoolAssistanceWithdrawRequest({required this.operationId});

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SchoolAssistanceWithdrawRequest &&
          other.operationId == operationId;

  @override
  int get hashCode => operationId.hashCode;

  factory SchoolAssistanceWithdrawRequest.fromJson(Map<String, dynamic> json) =>
      _$SchoolAssistanceWithdrawRequestFromJson(json);

  Map<String, dynamic> toJson() =>
      _$SchoolAssistanceWithdrawRequestToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
