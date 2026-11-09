//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'decide_bursary_application_request.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class DecideBursaryApplicationRequest {
  /// Returns a new [DecideBursaryApplicationRequest] instance.
  DecideBursaryApplicationRequest({
    required this.operationId,

    required this.revision,

    required this.outcome,

    required this.reasons,

    this.awardKind,

    this.awardValue,

    this.currency,
  });

  /// Retry the same request with the same value.
  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(
    name: r'outcome',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        DecideBursaryApplicationRequestOutcomeEnum.unknownDefaultOpenApi,
  )
  final DecideBursaryApplicationRequestOutcomeEnum outcome;

  @JsonKey(name: r'reasons', required: true, includeIfNull: false)
  final String reasons;

  @JsonKey(
    name: r'award_kind',
    required: false,
    includeIfNull: false,
    unknownEnumValue:
        DecideBursaryApplicationRequestAwardKindEnum.unknownDefaultOpenApi,
  )
  final DecideBursaryApplicationRequestAwardKindEnum? awardKind;

  /// Integer cents (fixed) or basis points (percent).
  // minimum: 1
  @JsonKey(name: r'award_value', required: false, includeIfNull: false)
  final int? awardValue;

  @JsonKey(name: r'currency', required: false, includeIfNull: false)
  final String? currency;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is DecideBursaryApplicationRequest &&
          other.operationId == operationId &&
          other.revision == revision &&
          other.outcome == outcome &&
          other.reasons == reasons &&
          other.awardKind == awardKind &&
          other.awardValue == awardValue &&
          other.currency == currency;

  @override
  int get hashCode =>
      operationId.hashCode +
      revision.hashCode +
      outcome.hashCode +
      reasons.hashCode +
      awardKind.hashCode +
      awardValue.hashCode +
      currency.hashCode;

  factory DecideBursaryApplicationRequest.fromJson(Map<String, dynamic> json) =>
      _$DecideBursaryApplicationRequestFromJson(json);

  Map<String, dynamic> toJson() =>
      _$DecideBursaryApplicationRequestToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum DecideBursaryApplicationRequestOutcomeEnum {
  @JsonValue(r'award')
  award(r'award'),
  @JsonValue(r'decline')
  decline(r'decline'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const DecideBursaryApplicationRequestOutcomeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum DecideBursaryApplicationRequestAwardKindEnum {
  @JsonValue(r'fixed')
  fixed(r'fixed'),
  @JsonValue(r'percent')
  percent(r'percent'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const DecideBursaryApplicationRequestAwardKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
