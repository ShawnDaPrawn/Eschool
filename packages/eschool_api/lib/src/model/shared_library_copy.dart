//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'shared_library_copy.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SharedLibraryCopy {
  /// Returns a new [SharedLibraryCopy] instance.
  SharedLibraryCopy({
    required this.id,

    required this.versionId,

    required this.revision,

    required this.active,

    required this.libraryItemId,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'version_id', required: true, includeIfNull: false)
  final int versionId;

  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'active', required: true, includeIfNull: false)
  final bool active;

  @JsonKey(name: r'library_item_id', required: true, includeIfNull: false)
  final int libraryItemId;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SharedLibraryCopy &&
          other.id == id &&
          other.versionId == versionId &&
          other.revision == revision &&
          other.active == active &&
          other.libraryItemId == libraryItemId;

  @override
  int get hashCode =>
      id.hashCode +
      versionId.hashCode +
      revision.hashCode +
      active.hashCode +
      libraryItemId.hashCode;

  factory SharedLibraryCopy.fromJson(Map<String, dynamic> json) =>
      _$SharedLibraryCopyFromJson(json);

  Map<String, dynamic> toJson() => _$SharedLibraryCopyToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
