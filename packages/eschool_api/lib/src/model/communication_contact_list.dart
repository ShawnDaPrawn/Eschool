//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/communication_configuration_years_inner.dart';
import 'package:json_annotation/json_annotation.dart';

part 'communication_contact_list.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CommunicationContactList {
  /// Returns a new [CommunicationContactList] instance.
  CommunicationContactList({
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
  final List<CommunicationConfigurationYearsInner> items;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CommunicationContactList &&
          other.page == page &&
          other.lastPage == lastPage &&
          other.items == items;

  @override
  int get hashCode => page.hashCode + lastPage.hashCode + items.hashCode;

  factory CommunicationContactList.fromJson(Map<String, dynamic> json) =>
      _$CommunicationContactListFromJson(json);

  Map<String, dynamic> toJson() => _$CommunicationContactListToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
