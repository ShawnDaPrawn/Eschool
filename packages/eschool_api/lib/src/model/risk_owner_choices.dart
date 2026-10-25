//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/risk_owner_choice.dart';
import 'package:json_annotation/json_annotation.dart';

part 'risk_owner_choices.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class RiskOwnerChoices {
  /// Returns a new [RiskOwnerChoices] instance.
  RiskOwnerChoices({
    required this.page,

    required this.lastPage,

    required this.owners,
  });

  // minimum: 1
  @JsonKey(name: r'page', required: true, includeIfNull: false)
  final int page;

  // minimum: 1
  @JsonKey(name: r'last_page', required: true, includeIfNull: false)
  final int lastPage;

  @JsonKey(name: r'owners', required: true, includeIfNull: false)
  final List<RiskOwnerChoice> owners;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is RiskOwnerChoices &&
          other.page == page &&
          other.lastPage == lastPage &&
          other.owners == owners;

  @override
  int get hashCode => page.hashCode + lastPage.hashCode + owners.hashCode;

  factory RiskOwnerChoices.fromJson(Map<String, dynamic> json) =>
      _$RiskOwnerChoicesFromJson(json);

  Map<String, dynamic> toJson() => _$RiskOwnerChoicesToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
