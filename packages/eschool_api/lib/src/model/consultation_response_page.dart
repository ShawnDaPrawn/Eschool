//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/consultation_response_entry.dart';
import 'package:json_annotation/json_annotation.dart';

part 'consultation_response_page.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ConsultationResponsePage {
  /// Returns a new [ConsultationResponsePage] instance.
  ConsultationResponsePage({
    required this.items,

    required this.page,

    required this.lastPage,
  });

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<ConsultationResponseEntry> items;

  // minimum: 1
  @JsonKey(name: r'page', required: true, includeIfNull: false)
  final int page;

  // minimum: 1
  @JsonKey(name: r'last_page', required: true, includeIfNull: false)
  final int lastPage;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ConsultationResponsePage &&
          other.items == items &&
          other.page == page &&
          other.lastPage == lastPage;

  @override
  int get hashCode => items.hashCode + page.hashCode + lastPage.hashCode;

  factory ConsultationResponsePage.fromJson(Map<String, dynamic> json) =>
      _$ConsultationResponsePageFromJson(json);

  Map<String, dynamic> toJson() => _$ConsultationResponsePageToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
