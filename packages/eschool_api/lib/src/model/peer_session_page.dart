//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/peer_session.dart';
import 'package:json_annotation/json_annotation.dart';

part 'peer_session_page.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PeerSessionPage {
  /// Returns a new [PeerSessionPage] instance.
  PeerSessionPage({
    required this.schoolId,

    required this.readerId,

    required this.items,

    required this.nextPage,
  });

  // minimum: 1
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  // minimum: 1
  @JsonKey(name: r'reader_id', required: true, includeIfNull: false)
  final int readerId;

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<PeerSession> items;

  // minimum: 1
  @JsonKey(name: r'next_page', required: true, includeIfNull: true)
  final int? nextPage;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PeerSessionPage &&
          other.schoolId == schoolId &&
          other.readerId == readerId &&
          other.items == items &&
          other.nextPage == nextPage;

  @override
  int get hashCode =>
      schoolId.hashCode +
      readerId.hashCode +
      items.hashCode +
      (nextPage == null ? 0 : nextPage.hashCode);

  factory PeerSessionPage.fromJson(Map<String, dynamic> json) =>
      _$PeerSessionPageFromJson(json);

  Map<String, dynamic> toJson() => _$PeerSessionPageToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
