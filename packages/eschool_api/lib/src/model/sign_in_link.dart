//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/sign_in_provider_name.dart';
import 'package:json_annotation/json_annotation.dart';

part 'sign_in_link.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SignInLink {
  /// Returns a new [SignInLink] instance.
  SignInLink({
    required this.id,

    required this.provider,

    required this.label,

    required this.email,

    required this.linkedAt,

    required this.lastUsedAt,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(
    name: r'provider',
    required: true,
    includeIfNull: false,
    unknownEnumValue: SignInProviderName.unknownDefaultOpenApi,
  )
  final SignInProviderName provider;

  @JsonKey(name: r'label', required: true, includeIfNull: false)
  final String label;

  /// The verified email the provider gave when the link was made.
  @JsonKey(name: r'email', required: true, includeIfNull: false)
  final String email;

  @JsonKey(name: r'linked_at', required: true, includeIfNull: true)
  final DateTime? linkedAt;

  @JsonKey(name: r'last_used_at', required: true, includeIfNull: true)
  final DateTime? lastUsedAt;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SignInLink &&
          other.id == id &&
          other.provider == provider &&
          other.label == label &&
          other.email == email &&
          other.linkedAt == linkedAt &&
          other.lastUsedAt == lastUsedAt;

  @override
  int get hashCode =>
      id.hashCode +
      provider.hashCode +
      label.hashCode +
      email.hashCode +
      (linkedAt == null ? 0 : linkedAt.hashCode) +
      (lastUsedAt == null ? 0 : lastUsedAt.hashCode);

  factory SignInLink.fromJson(Map<String, dynamic> json) =>
      _$SignInLinkFromJson(json);

  Map<String, dynamic> toJson() => _$SignInLinkToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
