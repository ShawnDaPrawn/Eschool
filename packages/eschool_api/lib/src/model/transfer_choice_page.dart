//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/transfer_choice.dart';
import 'package:json_annotation/json_annotation.dart';

part 'transfer_choice_page.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class TransferChoicePage {
  /// Returns a new [TransferChoicePage] instance.
  TransferChoicePage({
    required this.items,

    required this.page,

    required this.hasMore,
  });

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<TransferChoice> items;

  // minimum: 1
  @JsonKey(name: r'page', required: true, includeIfNull: false)
  final int page;

  @JsonKey(name: r'has_more', required: true, includeIfNull: false)
  final bool hasMore;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TransferChoicePage &&
          other.items == items &&
          other.page == page &&
          other.hasMore == hasMore;

  @override
  int get hashCode => items.hashCode + page.hashCode + hasMore.hashCode;

  factory TransferChoicePage.fromJson(Map<String, dynamic> json) =>
      _$TransferChoicePageFromJson(json);

  Map<String, dynamic> toJson() => _$TransferChoicePageToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
