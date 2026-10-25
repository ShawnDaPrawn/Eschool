//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'gallery_decision.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class GalleryDecision {
  /// Returns a new [GalleryDecision] instance.
  GalleryDecision({
    required this.operationId,

    required this.revision,

    required this.decision,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(
    name: r'decision',
    required: true,
    includeIfNull: false,
    unknownEnumValue: GalleryDecisionDecisionEnum.unknownDefaultOpenApi,
  )
  final GalleryDecisionDecisionEnum decision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is GalleryDecision &&
          other.operationId == operationId &&
          other.revision == revision &&
          other.decision == decision;

  @override
  int get hashCode =>
      operationId.hashCode + revision.hashCode + decision.hashCode;

  factory GalleryDecision.fromJson(Map<String, dynamic> json) =>
      _$GalleryDecisionFromJson(json);

  Map<String, dynamic> toJson() => _$GalleryDecisionToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum GalleryDecisionDecisionEnum {
  @JsonValue(r'approve')
  approve(r'approve'),
  @JsonValue(r'reject')
  reject(r'reject'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const GalleryDecisionDecisionEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
