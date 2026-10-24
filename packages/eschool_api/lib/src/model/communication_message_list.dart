//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/communication_summary.dart';
import 'package:json_annotation/json_annotation.dart';

part 'communication_message_list.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CommunicationMessageList {
  /// Returns a new [CommunicationMessageList] instance.
  CommunicationMessageList({
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
  final List<CommunicationSummary> items;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CommunicationMessageList &&
          other.page == page &&
          other.lastPage == lastPage &&
          other.items == items;

  @override
  int get hashCode => page.hashCode + lastPage.hashCode + items.hashCode;

  factory CommunicationMessageList.fromJson(Map<String, dynamic> json) =>
      _$CommunicationMessageListFromJson(json);

  Map<String, dynamic> toJson() => _$CommunicationMessageListToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
