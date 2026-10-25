//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/consultation_history_entry.dart';
import 'package:json_annotation/json_annotation.dart';

part 'consultation_history_page.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ConsultationHistoryPage {
  /// Returns a new [ConsultationHistoryPage] instance.
  ConsultationHistoryPage({
    required this.items,

    required this.page,

    required this.lastPage,
  });

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<ConsultationHistoryEntry> items;

  // minimum: 1
  @JsonKey(name: r'page', required: true, includeIfNull: false)
  final int page;

  // minimum: 1
  @JsonKey(name: r'last_page', required: true, includeIfNull: false)
  final int lastPage;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ConsultationHistoryPage &&
          other.items == items &&
          other.page == page &&
          other.lastPage == lastPage;

  @override
  int get hashCode => items.hashCode + page.hashCode + lastPage.hashCode;

  factory ConsultationHistoryPage.fromJson(Map<String, dynamic> json) =>
      _$ConsultationHistoryPageFromJson(json);

  Map<String, dynamic> toJson() => _$ConsultationHistoryPageToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
