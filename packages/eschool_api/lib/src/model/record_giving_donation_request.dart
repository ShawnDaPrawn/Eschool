//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'record_giving_donation_request.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class RecordGivingDonationRequest {
  /// Returns a new [RecordGivingDonationRequest] instance.
  RecordGivingDonationRequest({
    required this.operationId,

    required this.donorName,

    this.donorEmail,

    required this.amountCents,

    required this.receivedOn,

    required this.method,

    required this.sourceReference,

    this.pledgeId,

    required this.confirmed,
  });

  /// Retry the same request with the same value.
  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @JsonKey(name: r'donor_name', required: true, includeIfNull: false)
  final String donorName;

  @JsonKey(name: r'donor_email', required: false, includeIfNull: false)
  final String? donorEmail;

  // minimum: 1
  @JsonKey(name: r'amount_cents', required: true, includeIfNull: false)
  final int amountCents;

  @JsonKey(name: r'received_on', required: true, includeIfNull: false)
  final DateTime receivedOn;

  @JsonKey(
    name: r'method',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        RecordGivingDonationRequestMethodEnum.unknownDefaultOpenApi,
  )
  final RecordGivingDonationRequestMethodEnum method;

  @JsonKey(name: r'source_reference', required: true, includeIfNull: false)
  final String sourceReference;

  @JsonKey(name: r'pledge_id', required: false, includeIfNull: false)
  final int? pledgeId;

  @JsonKey(
    name: r'confirmed',
    required: true,
    includeIfNull: false,
    fromJson: RecordGivingDonationRequestConfirmedEnum.fromJson,
    toJson: RecordGivingDonationRequestConfirmedEnum.toJsonValue,
  )
  final RecordGivingDonationRequestConfirmedEnum confirmed;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is RecordGivingDonationRequest &&
          other.operationId == operationId &&
          other.donorName == donorName &&
          other.donorEmail == donorEmail &&
          other.amountCents == amountCents &&
          other.receivedOn == receivedOn &&
          other.method == method &&
          other.sourceReference == sourceReference &&
          other.pledgeId == pledgeId &&
          other.confirmed == confirmed;

  @override
  int get hashCode =>
      operationId.hashCode +
      donorName.hashCode +
      (donorEmail == null ? 0 : donorEmail.hashCode) +
      amountCents.hashCode +
      receivedOn.hashCode +
      method.hashCode +
      sourceReference.hashCode +
      (pledgeId == null ? 0 : pledgeId.hashCode) +
      confirmed.hashCode;

  factory RecordGivingDonationRequest.fromJson(Map<String, dynamic> json) =>
      _$RecordGivingDonationRequestFromJson(json);

  Map<String, dynamic> toJson() => _$RecordGivingDonationRequestToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum RecordGivingDonationRequestMethodEnum {
  @JsonValue(r'eft')
  eft(r'eft'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const RecordGivingDonationRequestMethodEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum RecordGivingDonationRequestConfirmedEnum {
  true_(true),
  unknownDefaultOpenApi(null);

  const RecordGivingDonationRequestConfirmedEnum(this.value);
  final bool? value;

  static RecordGivingDonationRequestConfirmedEnum fromJson(Object? value) =>
      values.firstWhere(
        (entry) => entry.value != null && entry.value == value,
        orElse: () =>
            RecordGivingDonationRequestConfirmedEnum.unknownDefaultOpenApi,
      );
  static bool? toJsonValue(RecordGivingDonationRequestConfirmedEnum value) =>
      value.value;
}
