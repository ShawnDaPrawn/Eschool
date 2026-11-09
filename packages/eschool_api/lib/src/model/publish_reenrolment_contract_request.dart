//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'publish_reenrolment_contract_request.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PublishReenrolmentContractRequest {
  /// Returns a new [PublishReenrolmentContractRequest] instance.
  PublishReenrolmentContractRequest({
    required this.operationId,

    required this.sessionYearId,

    required this.title,

    required this.body,

    required this.acceptanceRule,

    required this.respondBy,
  });

  /// Retry the same request with the same value.
  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  @JsonKey(name: r'title', required: true, includeIfNull: false)
  final String title;

  @JsonKey(name: r'body', required: true, includeIfNull: false)
  final String body;

  @JsonKey(
    name: r'acceptance_rule',
    required: true,
    includeIfNull: false,
    unknownEnumValue: PublishReenrolmentContractRequestAcceptanceRuleEnum
        .unknownDefaultOpenApi,
  )
  final PublishReenrolmentContractRequestAcceptanceRuleEnum acceptanceRule;

  @JsonKey(name: r'respond_by', required: true, includeIfNull: false)
  final DateTime respondBy;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PublishReenrolmentContractRequest &&
          other.operationId == operationId &&
          other.sessionYearId == sessionYearId &&
          other.title == title &&
          other.body == body &&
          other.acceptanceRule == acceptanceRule &&
          other.respondBy == respondBy;

  @override
  int get hashCode =>
      operationId.hashCode +
      sessionYearId.hashCode +
      title.hashCode +
      body.hashCode +
      acceptanceRule.hashCode +
      respondBy.hashCode;

  factory PublishReenrolmentContractRequest.fromJson(
    Map<String, dynamic> json,
  ) => _$PublishReenrolmentContractRequestFromJson(json);

  Map<String, dynamic> toJson() =>
      _$PublishReenrolmentContractRequestToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum PublishReenrolmentContractRequestAcceptanceRuleEnum {
  @JsonValue(r'all_fee_payers')
  allFeePayers(r'all_fee_payers'),
  @JsonValue(r'fee_payer')
  feePayer(r'fee_payer'),
  @JsonValue(r'any_guardian')
  anyGuardian(r'any_guardian'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const PublishReenrolmentContractRequestAcceptanceRuleEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
