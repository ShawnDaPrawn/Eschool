//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/support_version.dart';
import 'package:json_annotation/json_annotation.dart';

part 'support_versions.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SupportVersions {
  /// Returns a new [SupportVersions] instance.
  SupportVersions({
    required this.planId,

    required this.page,

    required this.lastPage,

    required this.items,
  });

  // minimum: 1
  @JsonKey(name: r'plan_id', required: true, includeIfNull: false)
  final int planId;

  // minimum: 1
  @JsonKey(name: r'page', required: true, includeIfNull: false)
  final int page;

  // minimum: 1
  @JsonKey(name: r'last_page', required: true, includeIfNull: false)
  final int lastPage;

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<SupportVersion> items;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SupportVersions &&
          other.planId == planId &&
          other.page == page &&
          other.lastPage == lastPage &&
          other.items == items;

  @override
  int get hashCode =>
      planId.hashCode + page.hashCode + lastPage.hashCode + items.hashCode;

  factory SupportVersions.fromJson(Map<String, dynamic> json) =>
      _$SupportVersionsFromJson(json);

  Map<String, dynamic> toJson() => _$SupportVersionsToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
