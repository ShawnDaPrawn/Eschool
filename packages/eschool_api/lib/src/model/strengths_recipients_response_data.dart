//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/strengths_recipient.dart';
import 'package:json_annotation/json_annotation.dart';

part 'strengths_recipients_response_data.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class StrengthsRecipientsResponseData {
  /// Returns a new [StrengthsRecipientsResponseData] instance.
  StrengthsRecipientsResponseData({required this.recipients});

  @JsonKey(name: r'recipients', required: true, includeIfNull: false)
  final List<StrengthsRecipient> recipients;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is StrengthsRecipientsResponseData &&
          other.recipients == recipients;

  @override
  int get hashCode => recipients.hashCode;

  factory StrengthsRecipientsResponseData.fromJson(Map<String, dynamic> json) =>
      _$StrengthsRecipientsResponseDataFromJson(json);

  Map<String, dynamic> toJson() =>
      _$StrengthsRecipientsResponseDataToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
