//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/academic_register_entry_revision.dart';
import 'package:json_annotation/json_annotation.dart';

part 'academic_register_sync_data.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicRegisterSyncData {
  /// Returns a new [AcademicRegisterSyncData] instance.
  AcademicRegisterSyncData({
    required this.registerId,

    required this.revision,

    required this.entries,
  });

  // minimum: 1
  @JsonKey(name: r'register_id', required: true, includeIfNull: false)
  final int registerId;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'entries', required: true, includeIfNull: false)
  final List<AcademicRegisterEntryRevision> entries;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicRegisterSyncData &&
          other.registerId == registerId &&
          other.revision == revision &&
          other.entries == entries;

  @override
  int get hashCode =>
      registerId.hashCode + revision.hashCode + entries.hashCode;

  factory AcademicRegisterSyncData.fromJson(Map<String, dynamic> json) =>
      _$AcademicRegisterSyncDataFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicRegisterSyncDataToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
