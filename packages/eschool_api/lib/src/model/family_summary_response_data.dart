//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/family_summary_response_data_sections_inner.dart';
import 'package:json_annotation/json_annotation.dart';

part 'family_summary_response_data.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FamilySummaryResponseData {
  /// Returns a new [FamilySummaryResponseData] instance.
  FamilySummaryResponseData({
    required this.id,

    required this.date,

    required this.title,

    required this.sections,

    required this.privateUpdates,

    required this.notificationsTarget,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'date', required: true, includeIfNull: false)
  final DateTime date;

  @JsonKey(name: r'title', required: true, includeIfNull: false)
  final String title;

  @JsonKey(name: r'sections', required: true, includeIfNull: false)
  final List<FamilySummaryResponseDataSectionsInner> sections;

  // minimum: 0
  @JsonKey(name: r'private_updates', required: true, includeIfNull: false)
  final int privateUpdates;

  /// The app path where private updates are read.
  @JsonKey(name: r'notifications_target', required: true, includeIfNull: false)
  final String notificationsTarget;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FamilySummaryResponseData &&
          other.id == id &&
          other.date == date &&
          other.title == title &&
          other.sections == sections &&
          other.privateUpdates == privateUpdates &&
          other.notificationsTarget == notificationsTarget;

  @override
  int get hashCode =>
      id.hashCode +
      date.hashCode +
      title.hashCode +
      sections.hashCode +
      privateUpdates.hashCode +
      notificationsTarget.hashCode;

  factory FamilySummaryResponseData.fromJson(Map<String, dynamic> json) =>
      _$FamilySummaryResponseDataFromJson(json);

  Map<String, dynamic> toJson() => _$FamilySummaryResponseDataToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
