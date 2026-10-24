//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'content_saved_access_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ContentSavedAccessWrite {
  /// Returns a new [ContentSavedAccessWrite] instance.
  ContentSavedAccessWrite({required this.versionIds});

  @JsonKey(name: r'version_ids', required: true, includeIfNull: false)
  final Set<int> versionIds;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ContentSavedAccessWrite && other.versionIds == versionIds;

  @override
  int get hashCode => versionIds.hashCode;

  factory ContentSavedAccessWrite.fromJson(Map<String, dynamic> json) =>
      _$ContentSavedAccessWriteFromJson(json);

  Map<String, dynamic> toJson() => _$ContentSavedAccessWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
