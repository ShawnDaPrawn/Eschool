//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'peer_session_correction.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PeerSessionCorrection {
  /// Returns a new [PeerSessionCorrection] instance.
  PeerSessionCorrection({
    required this.persona,

    required this.operationId,

    required this.revision,

    required this.reason,
  });

  @JsonKey(
    name: r'persona',
    required: true,
    includeIfNull: false,
    unknownEnumValue: PeerSessionCorrectionPersonaEnum.unknownDefaultOpenApi,
  )
  final PeerSessionCorrectionPersonaEnum persona;

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'reason', required: true, includeIfNull: false)
  final String reason;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PeerSessionCorrection &&
          other.persona == persona &&
          other.operationId == operationId &&
          other.revision == revision &&
          other.reason == reason;

  @override
  int get hashCode =>
      persona.hashCode +
      operationId.hashCode +
      revision.hashCode +
      reason.hashCode;

  factory PeerSessionCorrection.fromJson(Map<String, dynamic> json) =>
      _$PeerSessionCorrectionFromJson(json);

  Map<String, dynamic> toJson() => _$PeerSessionCorrectionToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum PeerSessionCorrectionPersonaEnum {
  @JsonValue(r'staff')
  staff(r'staff'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const PeerSessionCorrectionPersonaEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
