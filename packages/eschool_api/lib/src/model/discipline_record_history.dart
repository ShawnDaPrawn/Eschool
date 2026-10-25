//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/discipline_record_version.dart';
import 'package:json_annotation/json_annotation.dart';

part 'discipline_record_history.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class DisciplineRecordHistory {
  /// Returns a new [DisciplineRecordHistory] instance.
  DisciplineRecordHistory({required this.recordId, required this.versions});

  // minimum: 1
  @JsonKey(name: r'record_id', required: true, includeIfNull: false)
  final int recordId;

  @JsonKey(name: r'versions', required: true, includeIfNull: false)
  final List<DisciplineRecordVersion> versions;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is DisciplineRecordHistory &&
          other.recordId == recordId &&
          other.versions == versions;

  @override
  int get hashCode => recordId.hashCode + versions.hashCode;

  factory DisciplineRecordHistory.fromJson(Map<String, dynamic> json) =>
      _$DisciplineRecordHistoryFromJson(json);

  Map<String, dynamic> toJson() => _$DisciplineRecordHistoryToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
