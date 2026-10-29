//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'commerce_catalogue_vendor.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CommerceCatalogueVendor {
  /// Returns a new [CommerceCatalogueVendor] instance.
  CommerceCatalogueVendor({
    required this.id,

    required this.name,

    required this.description,

    required this.kind,
  });

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'description', required: true, includeIfNull: true)
  final String? description;

  @JsonKey(
    name: r'kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue: CommerceCatalogueVendorKindEnum.unknownDefaultOpenApi,
  )
  final CommerceCatalogueVendorKindEnum kind;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CommerceCatalogueVendor &&
          other.id == id &&
          other.name == name &&
          other.description == description &&
          other.kind == kind;

  @override
  int get hashCode =>
      id.hashCode +
      name.hashCode +
      (description == null ? 0 : description.hashCode) +
      kind.hashCode;

  factory CommerceCatalogueVendor.fromJson(Map<String, dynamic> json) =>
      _$CommerceCatalogueVendorFromJson(json);

  Map<String, dynamic> toJson() => _$CommerceCatalogueVendorToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum CommerceCatalogueVendorKindEnum {
  @JsonValue(r'school')
  school(r'school'),
  @JsonValue(r'independent')
  independent(r'independent'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const CommerceCatalogueVendorKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
