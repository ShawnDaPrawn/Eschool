//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'peer_session_complete.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PeerSessionComplete {
  /// Returns a new [PeerSessionComplete] instance.
  PeerSessionComplete({
    required this.persona,

    required this.operationId,

    required this.revision,

    required this.minutes,

    this.pointCategoryId,

    this.points,
  });

  @JsonKey(
    name: r'persona',
    required: true,
    includeIfNull: false,
    unknownEnumValue: PeerSessionCompletePersonaEnum.unknownDefaultOpenApi,
  )
  final PeerSessionCompletePersonaEnum persona;

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  // minimum: 1
  // maximum: 180
  @JsonKey(name: r'minutes', required: true, includeIfNull: false)
  final int minutes;

  // minimum: 1
  @JsonKey(name: r'point_category_id', required: false, includeIfNull: false)
  final int? pointCategoryId;

  // minimum: 1
  // maximum: 10000
  @JsonKey(name: r'points', required: false, includeIfNull: false)
  final int? points;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PeerSessionComplete &&
          other.persona == persona &&
          other.operationId == operationId &&
          other.revision == revision &&
          other.minutes == minutes &&
          other.pointCategoryId == pointCategoryId &&
          other.points == points;

  @override
  int get hashCode =>
      persona.hashCode +
      operationId.hashCode +
      revision.hashCode +
      minutes.hashCode +
      (pointCategoryId == null ? 0 : pointCategoryId.hashCode) +
      (points == null ? 0 : points.hashCode);

  factory PeerSessionComplete.fromJson(Map<String, dynamic> json) =>
      _$PeerSessionCompleteFromJson(json);

  Map<String, dynamic> toJson() => _$PeerSessionCompleteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum PeerSessionCompletePersonaEnum {
  @JsonValue(r'staff')
  staff(r'staff'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const PeerSessionCompletePersonaEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
