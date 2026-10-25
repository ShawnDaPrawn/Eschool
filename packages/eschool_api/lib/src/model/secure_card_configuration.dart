//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'secure_card_configuration.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SecureCardConfiguration {
  /// Returns a new [SecureCardConfiguration] instance.
  SecureCardConfiguration({
    required this.canManage,

    required this.canManageReaders,

    required this.writable,

    required this.types,
  });

  @JsonKey(name: r'can_manage', required: true, includeIfNull: false)
  final bool canManage;

  @JsonKey(name: r'can_manage_readers', required: true, includeIfNull: false)
  final bool canManageReaders;

  @JsonKey(name: r'writable', required: true, includeIfNull: false)
  final bool writable;

  @JsonKey(name: r'types', required: true, includeIfNull: false)
  final List<String> types;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SecureCardConfiguration &&
          other.canManage == canManage &&
          other.canManageReaders == canManageReaders &&
          other.writable == writable &&
          other.types == types;

  @override
  int get hashCode =>
      canManage.hashCode +
      canManageReaders.hashCode +
      writable.hashCode +
      types.hashCode;

  factory SecureCardConfiguration.fromJson(Map<String, dynamic> json) =>
      _$SecureCardConfigurationFromJson(json);

  Map<String, dynamic> toJson() => _$SecureCardConfigurationToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
