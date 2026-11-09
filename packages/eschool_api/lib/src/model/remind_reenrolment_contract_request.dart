//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'remind_reenrolment_contract_request.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class RemindReenrolmentContractRequest {
  /// Returns a new [RemindReenrolmentContractRequest] instance.
  RemindReenrolmentContractRequest({required this.operationId});

  /// Retry the same request with the same value.
  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is RemindReenrolmentContractRequest &&
          other.operationId == operationId;

  @override
  int get hashCode => operationId.hashCode;

  factory RemindReenrolmentContractRequest.fromJson(
    Map<String, dynamic> json,
  ) => _$RemindReenrolmentContractRequestFromJson(json);

  Map<String, dynamic> toJson() =>
      _$RemindReenrolmentContractRequestToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
