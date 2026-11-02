//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/communication_delivery_counts.dart';
import 'package:json_annotation/json_annotation.dart';

part 'communication_message_report_channels.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CommunicationMessageReportChannels {
  /// Returns a new [CommunicationMessageReportChannels] instance.
  CommunicationMessageReportChannels({required this.email, required this.push});

  @JsonKey(name: r'email', required: true, includeIfNull: false)
  final CommunicationDeliveryCounts email;

  @JsonKey(name: r'push', required: true, includeIfNull: false)
  final CommunicationDeliveryCounts push;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CommunicationMessageReportChannels &&
          other.email == email &&
          other.push == push;

  @override
  int get hashCode => email.hashCode + push.hashCode;

  factory CommunicationMessageReportChannels.fromJson(
    Map<String, dynamic> json,
  ) => _$CommunicationMessageReportChannelsFromJson(json);

  Map<String, dynamic> toJson() =>
      _$CommunicationMessageReportChannelsToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
