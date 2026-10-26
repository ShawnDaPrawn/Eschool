//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/peer_choice.dart';
import 'package:json_annotation/json_annotation.dart';

part 'peer_roster.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PeerRoster {
  /// Returns a new [PeerRoster] instance.
  PeerRoster({
    required this.schoolId,

    required this.readerId,

    required this.teachingGroupId,

    required this.items,
  });

  // minimum: 1
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  // minimum: 1
  @JsonKey(name: r'reader_id', required: true, includeIfNull: false)
  final int readerId;

  // minimum: 1
  @JsonKey(name: r'teaching_group_id', required: true, includeIfNull: false)
  final int teachingGroupId;

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<PeerChoice> items;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PeerRoster &&
          other.schoolId == schoolId &&
          other.readerId == readerId &&
          other.teachingGroupId == teachingGroupId &&
          other.items == items;

  @override
  int get hashCode =>
      schoolId.hashCode +
      readerId.hashCode +
      teachingGroupId.hashCode +
      items.hashCode;

  factory PeerRoster.fromJson(Map<String, dynamic> json) =>
      _$PeerRosterFromJson(json);

  Map<String, dynamic> toJson() => _$PeerRosterToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
