//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'reenrolment_contract_published.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ReenrolmentContractPublished {
  /// Returns a new [ReenrolmentContractPublished] instance.
  ReenrolmentContractPublished({
    required this.id,

    required this.version,

    required this.textHash,

    required this.revision,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'version', required: true, includeIfNull: false)
  final int version;

  @JsonKey(name: r'text_hash', required: true, includeIfNull: false)
  final String textHash;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ReenrolmentContractPublished &&
          other.id == id &&
          other.version == version &&
          other.textHash == textHash &&
          other.revision == revision;

  @override
  int get hashCode =>
      id.hashCode + version.hashCode + textHash.hashCode + revision.hashCode;

  factory ReenrolmentContractPublished.fromJson(Map<String, dynamic> json) =>
      _$ReenrolmentContractPublishedFromJson(json);

  Map<String, dynamic> toJson() => _$ReenrolmentContractPublishedToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
