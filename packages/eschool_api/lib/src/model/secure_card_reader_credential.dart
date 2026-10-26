//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'secure_card_reader_credential.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SecureCardReaderCredential {
  /// Returns a new [SecureCardReaderCredential] instance.
  SecureCardReaderCredential({
    required this.id,

    required this.credential,

    required this.kind,

    required this.expiresAt,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final String id;

  @JsonKey(name: r'credential', required: true, includeIfNull: false)
  final String credential;

  @JsonKey(name: r'kind', required: true, includeIfNull: false)
  final String kind;

  @JsonKey(name: r'expires_at', required: true, includeIfNull: false)
  final DateTime expiresAt;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SecureCardReaderCredential &&
          other.id == id &&
          other.credential == credential &&
          other.kind == kind &&
          other.expiresAt == expiresAt;

  @override
  int get hashCode =>
      id.hashCode + credential.hashCode + kind.hashCode + expiresAt.hashCode;

  factory SecureCardReaderCredential.fromJson(Map<String, dynamic> json) =>
      _$SecureCardReaderCredentialFromJson(json);

  Map<String, dynamic> toJson() => _$SecureCardReaderCredentialToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
