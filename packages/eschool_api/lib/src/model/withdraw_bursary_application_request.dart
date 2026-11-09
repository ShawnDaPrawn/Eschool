//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'withdraw_bursary_application_request.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WithdrawBursaryApplicationRequest {
  /// Returns a new [WithdrawBursaryApplicationRequest] instance.
  WithdrawBursaryApplicationRequest({
    required this.operationId,

    required this.revision,

    required this.confirmed,
  });

  /// Retry the same request with the same value.
  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(
    name: r'confirmed',
    required: true,
    includeIfNull: false,
    fromJson: WithdrawBursaryApplicationRequestConfirmedEnum.fromJson,
    toJson: WithdrawBursaryApplicationRequestConfirmedEnum.toJsonValue,
  )
  final WithdrawBursaryApplicationRequestConfirmedEnum confirmed;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WithdrawBursaryApplicationRequest &&
          other.operationId == operationId &&
          other.revision == revision &&
          other.confirmed == confirmed;

  @override
  int get hashCode =>
      operationId.hashCode + revision.hashCode + confirmed.hashCode;

  factory WithdrawBursaryApplicationRequest.fromJson(
    Map<String, dynamic> json,
  ) => _$WithdrawBursaryApplicationRequestFromJson(json);

  Map<String, dynamic> toJson() =>
      _$WithdrawBursaryApplicationRequestToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum WithdrawBursaryApplicationRequestConfirmedEnum {
  true_(true),
  unknownDefaultOpenApi(null);

  const WithdrawBursaryApplicationRequestConfirmedEnum(this.value);
  final bool? value;

  static WithdrawBursaryApplicationRequestConfirmedEnum fromJson(
    Object? value,
  ) => values.firstWhere(
    (entry) => entry.value != null && entry.value == value,
    orElse: () =>
        WithdrawBursaryApplicationRequestConfirmedEnum.unknownDefaultOpenApi,
  );
  static bool? toJsonValue(
    WithdrawBursaryApplicationRequestConfirmedEnum value,
  ) => value.value;
}
