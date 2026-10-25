//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/incident_public_thread.dart';
import 'package:eschool_api/src/model/incident_photo.dart';
import 'package:json_annotation/json_annotation.dart';

part 'incident_public_view.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class IncidentPublicView {
  /// Returns a new [IncidentPublicView] instance.
  IncidentPublicView({
    required this.anonymous,

    required this.body,

    required this.urgency,

    required this.status,

    required this.revision,

    required this.submittedAt,

    required this.emergencyGuidance,

    required this.threads,

    required this.hasOlder,

    required this.nextBefore,

    this.photos,
  });

  @JsonKey(name: r'anonymous', required: true, includeIfNull: false)
  final bool anonymous;

  @JsonKey(name: r'body', required: true, includeIfNull: false)
  final String body;

  @JsonKey(
    name: r'urgency',
    required: true,
    includeIfNull: false,
    unknownEnumValue: IncidentPublicViewUrgencyEnum.unknownDefaultOpenApi,
  )
  final IncidentPublicViewUrgencyEnum urgency;

  @JsonKey(
    name: r'status',
    required: true,
    includeIfNull: false,
    unknownEnumValue: IncidentPublicViewStatusEnum.unknownDefaultOpenApi,
  )
  final IncidentPublicViewStatusEnum status;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'submitted_at', required: true, includeIfNull: true)
  final DateTime? submittedAt;

  @JsonKey(name: r'emergency_guidance', required: true, includeIfNull: false)
  final String emergencyGuidance;

  @JsonKey(name: r'threads', required: true, includeIfNull: false)
  final List<IncidentPublicThread> threads;

  @JsonKey(name: r'has_older', required: true, includeIfNull: false)
  final bool hasOlder;

  // minimum: 1
  @JsonKey(name: r'next_before', required: true, includeIfNull: true)
  final int? nextBefore;

  @JsonKey(name: r'photos', required: false, includeIfNull: false)
  final List<IncidentPhoto>? photos;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is IncidentPublicView &&
          other.anonymous == anonymous &&
          other.body == body &&
          other.urgency == urgency &&
          other.status == status &&
          other.revision == revision &&
          other.submittedAt == submittedAt &&
          other.emergencyGuidance == emergencyGuidance &&
          other.threads == threads &&
          other.hasOlder == hasOlder &&
          other.nextBefore == nextBefore &&
          other.photos == photos;

  @override
  int get hashCode =>
      anonymous.hashCode +
      body.hashCode +
      urgency.hashCode +
      status.hashCode +
      revision.hashCode +
      (submittedAt == null ? 0 : submittedAt.hashCode) +
      emergencyGuidance.hashCode +
      threads.hashCode +
      hasOlder.hashCode +
      (nextBefore == null ? 0 : nextBefore.hashCode) +
      photos.hashCode;

  factory IncidentPublicView.fromJson(Map<String, dynamic> json) =>
      _$IncidentPublicViewFromJson(json);

  Map<String, dynamic> toJson() => _$IncidentPublicViewToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum IncidentPublicViewUrgencyEnum {
  @JsonValue(r'routine')
  routine(r'routine'),
  @JsonValue(r'urgent')
  urgent(r'urgent'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const IncidentPublicViewUrgencyEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum IncidentPublicViewStatusEnum {
  @JsonValue(r'open')
  open(r'open'),
  @JsonValue(r'triaged')
  triaged(r'triaged'),
  @JsonValue(r'resolved')
  resolved(r'resolved'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const IncidentPublicViewStatusEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
