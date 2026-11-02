//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/communication_message_report_items_inner.dart';
import 'package:eschool_api/src/model/communication_message_report_channels.dart';
import 'package:json_annotation/json_annotation.dart';

part 'communication_message_report.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CommunicationMessageReport {
  /// Returns a new [CommunicationMessageReport] instance.
  CommunicationMessageReport({
    required this.id,

    required this.title,

    required this.publishedAt,

    required this.recipientCount,

    required this.readCount,

    required this.notNotified,

    required this.channels,

    required this.page,

    required this.lastPage,

    required this.items,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'title', required: true, includeIfNull: false)
  final String title;

  @JsonKey(name: r'published_at', required: true, includeIfNull: false)
  final DateTime publishedAt;

  // minimum: 0
  @JsonKey(name: r'recipient_count', required: true, includeIfNull: false)
  final int recipientCount;

  // minimum: 0
  @JsonKey(name: r'read_count', required: true, includeIfNull: false)
  final int readCount;

  /// Recipients whose own choices or access left no notification channel. They can still read the message in the app.
  // minimum: 0
  @JsonKey(name: r'not_notified', required: true, includeIfNull: false)
  final int notNotified;

  @JsonKey(name: r'channels', required: true, includeIfNull: false)
  final CommunicationMessageReportChannels channels;

  // minimum: 1
  @JsonKey(name: r'page', required: true, includeIfNull: false)
  final int page;

  // minimum: 1
  @JsonKey(name: r'last_page', required: true, includeIfNull: false)
  final int lastPage;

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<CommunicationMessageReportItemsInner> items;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CommunicationMessageReport &&
          other.id == id &&
          other.title == title &&
          other.publishedAt == publishedAt &&
          other.recipientCount == recipientCount &&
          other.readCount == readCount &&
          other.notNotified == notNotified &&
          other.channels == channels &&
          other.page == page &&
          other.lastPage == lastPage &&
          other.items == items;

  @override
  int get hashCode =>
      id.hashCode +
      title.hashCode +
      publishedAt.hashCode +
      recipientCount.hashCode +
      readCount.hashCode +
      notNotified.hashCode +
      channels.hashCode +
      page.hashCode +
      lastPage.hashCode +
      items.hashCode;

  factory CommunicationMessageReport.fromJson(Map<String, dynamic> json) =>
      _$CommunicationMessageReportFromJson(json);

  Map<String, dynamic> toJson() => _$CommunicationMessageReportToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
