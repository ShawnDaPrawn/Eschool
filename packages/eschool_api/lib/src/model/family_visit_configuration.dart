//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/family_visit_rules.dart';
import 'package:eschool_api/src/model/family_visit_choice.dart';
import 'package:json_annotation/json_annotation.dart';

part 'family_visit_configuration.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FamilyVisitConfiguration {
  /// Returns a new [FamilyVisitConfiguration] instance.
  FamilyVisitConfiguration({
    required this.schoolId,

    required this.readerId,

    required this.timezone,

    required this.writable,

    required this.canManage,

    required this.canHost,

    required this.children,

    required this.hosts,

    required this.gateRules,
  });

  // minimum: 1
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  // minimum: 1
  @JsonKey(name: r'reader_id', required: true, includeIfNull: false)
  final int readerId;

  @JsonKey(name: r'timezone', required: true, includeIfNull: false)
  final String timezone;

  @JsonKey(name: r'writable', required: true, includeIfNull: false)
  final bool writable;

  @JsonKey(name: r'can_manage', required: true, includeIfNull: false)
  final bool canManage;

  @JsonKey(name: r'can_host', required: true, includeIfNull: false)
  final bool canHost;

  @JsonKey(name: r'children', required: true, includeIfNull: false)
  final List<FamilyVisitChoice> children;

  @JsonKey(name: r'hosts', required: true, includeIfNull: false)
  final List<FamilyVisitChoice> hosts;

  @JsonKey(name: r'gate_rules', required: true, includeIfNull: false)
  final FamilyVisitRules gateRules;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FamilyVisitConfiguration &&
          other.schoolId == schoolId &&
          other.readerId == readerId &&
          other.timezone == timezone &&
          other.writable == writable &&
          other.canManage == canManage &&
          other.canHost == canHost &&
          other.children == children &&
          other.hosts == hosts &&
          other.gateRules == gateRules;

  @override
  int get hashCode =>
      schoolId.hashCode +
      readerId.hashCode +
      timezone.hashCode +
      writable.hashCode +
      canManage.hashCode +
      canHost.hashCode +
      children.hashCode +
      hosts.hashCode +
      gateRules.hashCode;

  factory FamilyVisitConfiguration.fromJson(Map<String, dynamic> json) =>
      _$FamilyVisitConfigurationFromJson(json);

  Map<String, dynamic> toJson() => _$FamilyVisitConfigurationToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
