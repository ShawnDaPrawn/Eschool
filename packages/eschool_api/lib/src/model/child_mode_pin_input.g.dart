// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'child_mode_pin_input.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ChildModePinInput _$ChildModePinInputFromJson(Map<String, dynamic> json) =>
    $checkedCreate('ChildModePinInput', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['pin', 'pin_confirmation']);
      final val = ChildModePinInput(
        pin: $checkedConvert('pin', (v) => v as String),
        pinConfirmation: $checkedConvert(
          'pin_confirmation',
          (v) => v as String,
        ),
      );
      return val;
    }, fieldKeyMap: const {'pinConfirmation': 'pin_confirmation'});

Map<String, dynamic> _$ChildModePinInputToJson(ChildModePinInput instance) =>
    <String, dynamic>{
      'pin': instance.pin,
      'pin_confirmation': instance.pinConfirmation,
    };
