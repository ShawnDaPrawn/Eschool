//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/peer_choice.dart';
import 'package:eschool_api/src/model/peer_point_category.dart';
import 'package:json_annotation/json_annotation.dart';

part 'peer_configuration.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PeerConfiguration {
  /// Returns a new [PeerConfiguration] instance.
  PeerConfiguration({
    required this.schoolId,

    required this.readerId,

    required this.persona,

    required this.timezone,

    required this.writable,

    required this.groups,

    required this.children,

    required this.canAwardPoints,

    required this.pointCategories,
  });

  // minimum: 1
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  // minimum: 1
  @JsonKey(name: r'reader_id', required: true, includeIfNull: false)
  final int readerId;

  @JsonKey(
    name: r'persona',
    required: true,
    includeIfNull: false,
    unknownEnumValue: PeerConfigurationPersonaEnum.unknownDefaultOpenApi,
  )
  final PeerConfigurationPersonaEnum persona;

  @JsonKey(name: r'timezone', required: true, includeIfNull: false)
  final String timezone;

  @JsonKey(name: r'writable', required: true, includeIfNull: false)
  final bool writable;

  @JsonKey(name: r'groups', required: true, includeIfNull: false)
  final List<PeerChoice> groups;

  @JsonKey(name: r'children', required: true, includeIfNull: false)
  final List<PeerChoice> children;

  @JsonKey(name: r'can_award_points', required: true, includeIfNull: false)
  final bool canAwardPoints;

  @JsonKey(name: r'point_categories', required: true, includeIfNull: false)
  final List<PeerPointCategory> pointCategories;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PeerConfiguration &&
          other.schoolId == schoolId &&
          other.readerId == readerId &&
          other.persona == persona &&
          other.timezone == timezone &&
          other.writable == writable &&
          other.groups == groups &&
          other.children == children &&
          other.canAwardPoints == canAwardPoints &&
          other.pointCategories == pointCategories;

  @override
  int get hashCode =>
      schoolId.hashCode +
      readerId.hashCode +
      persona.hashCode +
      timezone.hashCode +
      writable.hashCode +
      groups.hashCode +
      children.hashCode +
      canAwardPoints.hashCode +
      pointCategories.hashCode;

  factory PeerConfiguration.fromJson(Map<String, dynamic> json) =>
      _$PeerConfigurationFromJson(json);

  Map<String, dynamic> toJson() => _$PeerConfigurationToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum PeerConfigurationPersonaEnum {
  @JsonValue(r'staff')
  staff(r'staff'),
  @JsonValue(r'parent')
  parent(r'parent'),
  @JsonValue(r'student')
  student(r'student'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const PeerConfigurationPersonaEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
