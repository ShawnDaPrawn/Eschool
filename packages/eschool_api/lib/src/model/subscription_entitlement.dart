//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'subscription_entitlement.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SubscriptionEntitlement {
  /// Returns a new [SubscriptionEntitlement] instance.
  SubscriptionEntitlement({
    required this.required_,

    required this.entitled,

    required this.source_,

    required this.until,
  });

  @JsonKey(name: r'required', required: true, includeIfNull: false)
  final bool required_;

  @JsonKey(name: r'entitled', required: true, includeIfNull: false)
  final bool entitled;

  @JsonKey(
    name: r'source',
    required: true,
    includeIfNull: true,
    unknownEnumValue: SubscriptionEntitlementSource_Enum.unknownDefaultOpenApi,
  )
  final SubscriptionEntitlementSource_Enum? source_;

  @JsonKey(name: r'until', required: true, includeIfNull: true)
  final DateTime? until;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SubscriptionEntitlement &&
          other.required_ == required_ &&
          other.entitled == entitled &&
          other.source_ == source_ &&
          other.until == until;

  @override
  int get hashCode =>
      required_.hashCode +
      entitled.hashCode +
      (source_ == null ? 0 : source_.hashCode) +
      (until == null ? 0 : until.hashCode);

  factory SubscriptionEntitlement.fromJson(Map<String, dynamic> json) =>
      _$SubscriptionEntitlementFromJson(json);

  Map<String, dynamic> toJson() => _$SubscriptionEntitlementToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum SubscriptionEntitlementSource_Enum {
  @JsonValue(r'included')
  included(r'included'),
  @JsonValue(r'school_licence')
  schoolLicence(r'school_licence'),
  @JsonValue(r'sponsored')
  sponsored(r'sponsored'),
  @JsonValue(r'subscription')
  subscription(r'subscription'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const SubscriptionEntitlementSource_Enum(this.value);

  final String value;

  @override
  String toString() => value;
}
