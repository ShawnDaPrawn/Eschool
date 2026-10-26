//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/peer_match.dart';
import 'package:json_annotation/json_annotation.dart';

part 'peer_match_page.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PeerMatchPage {
  /// Returns a new [PeerMatchPage] instance.
  PeerMatchPage({
    required this.schoolId,

    required this.readerId,

    required this.teachingGroupId,

    required this.learnerId,

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

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<PeerMatch> items;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PeerMatchPage &&
          other.schoolId == schoolId &&
          other.readerId == readerId &&
          other.teachingGroupId == teachingGroupId &&
          other.learnerId == learnerId &&
          other.items == items;

  @override
  int get hashCode =>
      schoolId.hashCode +
      readerId.hashCode +
      teachingGroupId.hashCode +
      learnerId.hashCode +
      items.hashCode;

  factory PeerMatchPage.fromJson(Map<String, dynamic> json) =>
      _$PeerMatchPageFromJson(json);

  Map<String, dynamic> toJson() => _$PeerMatchPageToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
