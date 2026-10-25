//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/consultation_summary.dart';
import 'package:json_annotation/json_annotation.dart';

part 'consultation_page.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ConsultationPage {
  /// Returns a new [ConsultationPage] instance.
  ConsultationPage({
    required this.items,

    required this.page,

    required this.lastPage,
  });

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<ConsultationSummary> items;

  // minimum: 1
  @JsonKey(name: r'page', required: true, includeIfNull: false)
  final int page;

  // minimum: 1
  @JsonKey(name: r'last_page', required: true, includeIfNull: false)
  final int lastPage;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ConsultationPage &&
          other.items == items &&
          other.page == page &&
          other.lastPage == lastPage;

  @override
  int get hashCode => items.hashCode + page.hashCode + lastPage.hashCode;

  factory ConsultationPage.fromJson(Map<String, dynamic> json) =>
      _$ConsultationPageFromJson(json);

  Map<String, dynamic> toJson() => _$ConsultationPageToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
