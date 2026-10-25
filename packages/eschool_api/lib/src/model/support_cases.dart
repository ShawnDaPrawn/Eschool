//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/support_case_summary.dart';
import 'package:json_annotation/json_annotation.dart';

part 'support_cases.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SupportCases {
  /// Returns a new [SupportCases] instance.
  SupportCases({
    required this.page,

    required this.lastPage,

    required this.items,
  });

  // minimum: 1
  @JsonKey(name: r'page', required: true, includeIfNull: false)
  final int page;

  // minimum: 1
  @JsonKey(name: r'last_page', required: true, includeIfNull: false)
  final int lastPage;

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<SupportCaseSummary> items;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SupportCases &&
          other.page == page &&
          other.lastPage == lastPage &&
          other.items == items;

  @override
  int get hashCode => page.hashCode + lastPage.hashCode + items.hashCode;

  factory SupportCases.fromJson(Map<String, dynamic> json) =>
      _$SupportCasesFromJson(json);

  Map<String, dynamic> toJson() => _$SupportCasesToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
