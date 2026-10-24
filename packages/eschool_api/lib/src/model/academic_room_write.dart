//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_room_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicRoomWrite {
  /// Returns a new [AcademicRoomWrite] instance.
  AcademicRoomWrite({
    required this.name,

    this.building,

    this.capacity,

    required this.type,

    required this.active,

    this.revision,
  });

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'building', required: false, includeIfNull: false)
  final String? building;

  // minimum: 1
  // maximum: 5000
  @JsonKey(name: r'capacity', required: false, includeIfNull: false)
  final int? capacity;

  @JsonKey(name: r'type', required: true, includeIfNull: false)
  final String type;

  @JsonKey(name: r'active', required: true, includeIfNull: false)
  final bool active;

  // minimum: 1
  @JsonKey(name: r'revision', required: false, includeIfNull: false)
  final int? revision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicRoomWrite &&
          other.name == name &&
          other.building == building &&
          other.capacity == capacity &&
          other.type == type &&
          other.active == active &&
          other.revision == revision;

  @override
  int get hashCode =>
      name.hashCode +
      (building == null ? 0 : building.hashCode) +
      (capacity == null ? 0 : capacity.hashCode) +
      type.hashCode +
      active.hashCode +
      revision.hashCode;

  factory AcademicRoomWrite.fromJson(Map<String, dynamic> json) =>
      _$AcademicRoomWriteFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicRoomWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
