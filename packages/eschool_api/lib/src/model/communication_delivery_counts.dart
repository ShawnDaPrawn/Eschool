//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'communication_delivery_counts.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CommunicationDeliveryCounts {
  /// Returns a new [CommunicationDeliveryCounts] instance.
  CommunicationDeliveryCounts({
    required this.delivered,

    required this.sent,

    required this.queued,

    required this.bounced,

    required this.complained,

    required this.failed,

    required this.suppressed,
  });

  // minimum: 0
  @JsonKey(name: r'delivered', required: true, includeIfNull: false)
  final int delivered;

  // minimum: 0
  @JsonKey(name: r'sent', required: true, includeIfNull: false)
  final int sent;

  // minimum: 0
  @JsonKey(name: r'queued', required: true, includeIfNull: false)
  final int queued;

  // minimum: 0
  @JsonKey(name: r'bounced', required: true, includeIfNull: false)
  final int bounced;

  // minimum: 0
  @JsonKey(name: r'complained', required: true, includeIfNull: false)
  final int complained;

  // minimum: 0
  @JsonKey(name: r'failed', required: true, includeIfNull: false)
  final int failed;

  // minimum: 0
  @JsonKey(name: r'suppressed', required: true, includeIfNull: false)
  final int suppressed;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CommunicationDeliveryCounts &&
          other.delivered == delivered &&
          other.sent == sent &&
          other.queued == queued &&
          other.bounced == bounced &&
          other.complained == complained &&
          other.failed == failed &&
          other.suppressed == suppressed;

  @override
  int get hashCode =>
      delivered.hashCode +
      sent.hashCode +
      queued.hashCode +
      bounced.hashCode +
      complained.hashCode +
      failed.hashCode +
      suppressed.hashCode;

  factory CommunicationDeliveryCounts.fromJson(Map<String, dynamic> json) =>
      _$CommunicationDeliveryCountsFromJson(json);

  Map<String, dynamic> toJson() => _$CommunicationDeliveryCountsToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
