//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'intervention_queue_item.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class InterventionQueueItem {
  /// Returns a new [InterventionQueueItem] instance.
  InterventionQueueItem({
    this.learnerName,

    this.ownerName,

    required this.id,

    required this.learnerId,

    required this.ownerId,

    required this.gradeLevelId,

    required this.reviewOn,

    required this.state,

    required this.revision,

    required this.canReadBody,
  });

  @JsonKey(name: r'learner_name', required: false, includeIfNull: false)
  final String? learnerName;

  @JsonKey(name: r'owner_name', required: false, includeIfNull: false)
  final String? ownerName;

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  // minimum: 1
  @JsonKey(name: r'owner_id', required: true, includeIfNull: false)
  final int ownerId;

  // minimum: 1
  @JsonKey(name: r'grade_level_id', required: true, includeIfNull: false)
  final int gradeLevelId;

  @JsonKey(name: r'review_on', required: true, includeIfNull: false)
  final String reviewOn;

  @JsonKey(
    name: r'state',
    required: true,
    includeIfNull: false,
    unknownEnumValue: InterventionQueueItemStateEnum.unknownDefaultOpenApi,
  )
  final InterventionQueueItemStateEnum state;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'can_read_body', required: true, includeIfNull: false)
  final bool canReadBody;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is InterventionQueueItem &&
          other.learnerName == learnerName &&
          other.ownerName == ownerName &&
          other.id == id &&
          other.learnerId == learnerId &&
          other.ownerId == ownerId &&
          other.gradeLevelId == gradeLevelId &&
          other.reviewOn == reviewOn &&
          other.state == state &&
          other.revision == revision &&
          other.canReadBody == canReadBody;

  @override
  int get hashCode =>
      learnerName.hashCode +
      ownerName.hashCode +
      id.hashCode +
      learnerId.hashCode +
      ownerId.hashCode +
      gradeLevelId.hashCode +
      reviewOn.hashCode +
      state.hashCode +
      revision.hashCode +
      canReadBody.hashCode;

  factory InterventionQueueItem.fromJson(Map<String, dynamic> json) =>
      _$InterventionQueueItemFromJson(json);

  Map<String, dynamic> toJson() => _$InterventionQueueItemToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum InterventionQueueItemStateEnum {
  @JsonValue(r'active')
  active(r'active'),
  @JsonValue(r'completed')
  completed(r'completed'),
  @JsonValue(r'cancelled')
  cancelled(r'cancelled'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const InterventionQueueItemStateEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
