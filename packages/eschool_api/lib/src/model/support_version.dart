//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/support_plan_snapshot.dart';
import 'package:json_annotation/json_annotation.dart';

part 'support_version.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SupportVersion {
  /// Returns a new [SupportVersion] instance.
  SupportVersion({
    required this.revision,

    required this.authorId,

    required this.recordedAt,

    required this.snapshot,
  });

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  // minimum: 1
  @JsonKey(name: r'author_id', required: true, includeIfNull: false)
  final int authorId;

  @JsonKey(name: r'recorded_at', required: true, includeIfNull: false)
  final DateTime recordedAt;

  @JsonKey(name: r'snapshot', required: true, includeIfNull: false)
  final SupportPlanSnapshot snapshot;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SupportVersion &&
          other.revision == revision &&
          other.authorId == authorId &&
          other.recordedAt == recordedAt &&
          other.snapshot == snapshot;

  @override
  int get hashCode =>
      revision.hashCode +
      authorId.hashCode +
      recordedAt.hashCode +
      snapshot.hashCode;

  factory SupportVersion.fromJson(Map<String, dynamic> json) =>
      _$SupportVersionFromJson(json);

  Map<String, dynamic> toJson() => _$SupportVersionToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
