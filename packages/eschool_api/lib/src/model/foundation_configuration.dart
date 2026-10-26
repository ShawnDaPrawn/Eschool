//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/foundation_group_choice.dart';
import 'package:eschool_api/src/model/foundation_tablet.dart';
import 'package:eschool_api/src/model/foundation_lease_summary.dart';
import 'package:json_annotation/json_annotation.dart';

part 'foundation_configuration.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FoundationConfiguration {
  /// Returns a new [FoundationConfiguration] instance.
  FoundationConfiguration({
    required this.schoolId,

    required this.readerId,

    required this.sessionYearId,

    required this.canManage,

    required this.canGrant,

    required this.writable,

    required this.page,

    required this.nextPage,

    required this.groups,

    required this.devices,

    required this.leases,
  });

  // minimum: 1
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  // minimum: 1
  @JsonKey(name: r'reader_id', required: true, includeIfNull: false)
  final int readerId;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  @JsonKey(name: r'can_manage', required: true, includeIfNull: false)
  final bool canManage;

  @JsonKey(name: r'can_grant', required: true, includeIfNull: false)
  final bool canGrant;

  @JsonKey(name: r'writable', required: true, includeIfNull: false)
  final bool writable;

  // minimum: 1
  @JsonKey(name: r'page', required: true, includeIfNull: false)
  final int page;

  // minimum: 1
  @JsonKey(name: r'next_page', required: true, includeIfNull: true)
  final int? nextPage;

  @JsonKey(name: r'groups', required: true, includeIfNull: false)
  final List<FoundationGroupChoice> groups;

  @JsonKey(name: r'devices', required: true, includeIfNull: false)
  final List<FoundationTablet> devices;

  @JsonKey(name: r'leases', required: true, includeIfNull: false)
  final List<FoundationLeaseSummary> leases;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FoundationConfiguration &&
          other.schoolId == schoolId &&
          other.readerId == readerId &&
          other.sessionYearId == sessionYearId &&
          other.canManage == canManage &&
          other.canGrant == canGrant &&
          other.writable == writable &&
          other.page == page &&
          other.nextPage == nextPage &&
          other.groups == groups &&
          other.devices == devices &&
          other.leases == leases;

  @override
  int get hashCode =>
      schoolId.hashCode +
      readerId.hashCode +
      sessionYearId.hashCode +
      canManage.hashCode +
      canGrant.hashCode +
      writable.hashCode +
      page.hashCode +
      (nextPage == null ? 0 : nextPage.hashCode) +
      groups.hashCode +
      devices.hashCode +
      leases.hashCode;

  factory FoundationConfiguration.fromJson(Map<String, dynamic> json) =>
      _$FoundationConfigurationFromJson(json);

  Map<String, dynamic> toJson() => _$FoundationConfigurationToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
