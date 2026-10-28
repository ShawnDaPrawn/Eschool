//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'commerce_seller.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CommerceSeller {
  /// Returns a new [CommerceSeller] instance.
  CommerceSeller({
    required this.id,

    required this.name,

    required this.legalName,

    required this.legalAddress,

    required this.kind,

    required this.vatRegistered,

    required this.vatNumber,
  });

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'legal_name', required: true, includeIfNull: false)
  final String legalName;

  @JsonKey(name: r'legal_address', required: true, includeIfNull: true)
  final String? legalAddress;

  @JsonKey(
    name: r'kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue: CommerceSellerKindEnum.unknownDefaultOpenApi,
  )
  final CommerceSellerKindEnum kind;

  @JsonKey(name: r'vat_registered', required: true, includeIfNull: false)
  final bool vatRegistered;

  @JsonKey(name: r'vat_number', required: true, includeIfNull: true)
  final String? vatNumber;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CommerceSeller &&
          other.id == id &&
          other.name == name &&
          other.legalName == legalName &&
          other.legalAddress == legalAddress &&
          other.kind == kind &&
          other.vatRegistered == vatRegistered &&
          other.vatNumber == vatNumber;

  @override
  int get hashCode =>
      id.hashCode +
      name.hashCode +
      legalName.hashCode +
      (legalAddress == null ? 0 : legalAddress.hashCode) +
      kind.hashCode +
      vatRegistered.hashCode +
      (vatNumber == null ? 0 : vatNumber.hashCode);

  factory CommerceSeller.fromJson(Map<String, dynamic> json) =>
      _$CommerceSellerFromJson(json);

  Map<String, dynamic> toJson() => _$CommerceSellerToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum CommerceSellerKindEnum {
  @JsonValue(r'school')
  school(r'school'),
  @JsonValue(r'independent')
  independent(r'independent'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const CommerceSellerKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
