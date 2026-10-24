//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_room.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicRoom {
  /// Returns a new [AcademicRoom] instance.
  AcademicRoom({
    required this.name,

    required this.building,

    required this.capacity,

    required this.type,

    required this.active,

    required this.id,

    required this.revision,
  });

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'building', required: true, includeIfNull: true)
  final String? building;

  // minimum: 1
  // maximum: 5000
  @JsonKey(name: r'capacity', required: true, includeIfNull: true)
  final int? capacity;

  @JsonKey(name: r'type', required: true, includeIfNull: false)
  final String type;

  @JsonKey(name: r'active', required: true, includeIfNull: false)
  final bool active;

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicRoom &&
          other.name == name &&
          other.building == building &&
          other.capacity == capacity &&
          other.type == type &&
          other.active == active &&
          other.id == id &&
          other.revision == revision;

  @override
  int get hashCode =>
      name.hashCode +
      (building == null ? 0 : building.hashCode) +
      (capacity == null ? 0 : capacity.hashCode) +
      type.hashCode +
      active.hashCode +
      id.hashCode +
      revision.hashCode;

  factory AcademicRoom.fromJson(Map<String, dynamic> json) =>
      _$AcademicRoomFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicRoomToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
