//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/practice_bank_item.dart';
import 'package:json_annotation/json_annotation.dart';

part 'practice_bank_page.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PracticeBankPage {
  /// Returns a new [PracticeBankPage] instance.
  PracticeBankPage({
    required this.items,

    required this.page,

    required this.lastPage,
  });

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<PracticeBankItem> items;

  // minimum: 1
  @JsonKey(name: r'page', required: true, includeIfNull: false)
  final int page;

  // minimum: 1
  @JsonKey(name: r'last_page', required: true, includeIfNull: false)
  final int lastPage;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PracticeBankPage &&
          other.items == items &&
          other.page == page &&
          other.lastPage == lastPage;

  @override
  int get hashCode => items.hashCode + page.hashCode + lastPage.hashCode;

  factory PracticeBankPage.fromJson(Map<String, dynamic> json) =>
      _$PracticeBankPageFromJson(json);

  Map<String, dynamic> toJson() => _$PracticeBankPageToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
