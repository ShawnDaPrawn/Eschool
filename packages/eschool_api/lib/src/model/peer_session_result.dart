//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/peer_session.dart';
import 'package:json_annotation/json_annotation.dart';

part 'peer_session_result.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PeerSessionResult {
  /// Returns a new [PeerSessionResult] instance.
  PeerSessionResult({
    required this.schoolId,

    required this.readerId,

    required this.session,
  });

  // minimum: 1
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  // minimum: 1
  @JsonKey(name: r'reader_id', required: true, includeIfNull: false)
  final int readerId;

  @JsonKey(name: r'session', required: true, includeIfNull: false)
  final PeerSession session;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PeerSessionResult &&
          other.schoolId == schoolId &&
          other.readerId == readerId &&
          other.session == session;

  @override
  int get hashCode => schoolId.hashCode + readerId.hashCode + session.hashCode;

  factory PeerSessionResult.fromJson(Map<String, dynamic> json) =>
      _$PeerSessionResultFromJson(json);

  Map<String, dynamic> toJson() => _$PeerSessionResultToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
