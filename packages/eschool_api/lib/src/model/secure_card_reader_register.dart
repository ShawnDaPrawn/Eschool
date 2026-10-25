//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'secure_card_reader_register.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SecureCardReaderRegister {
  /// Returns a new [SecureCardReaderRegister] instance.
  SecureCardReaderRegister({
    required this.operationId,

    required this.name,

    required this.kind,

    required this.publicKey,

    required this.days,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'kind', required: true, includeIfNull: false)
  final String kind;

  @JsonKey(name: r'public_key', required: true, includeIfNull: false)
  final String publicKey;

  // minimum: 1
  // maximum: 30
  @JsonKey(name: r'days', required: true, includeIfNull: false)
  final int days;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SecureCardReaderRegister &&
          other.operationId == operationId &&
          other.name == name &&
          other.kind == kind &&
          other.publicKey == publicKey &&
          other.days == days;

  @override
  int get hashCode =>
      operationId.hashCode +
      name.hashCode +
      kind.hashCode +
      publicKey.hashCode +
      days.hashCode;

  factory SecureCardReaderRegister.fromJson(Map<String, dynamic> json) =>
      _$SecureCardReaderRegisterFromJson(json);

  Map<String, dynamic> toJson() => _$SecureCardReaderRegisterToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
