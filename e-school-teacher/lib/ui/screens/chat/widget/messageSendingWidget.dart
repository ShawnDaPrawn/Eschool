import 'package:eschool_teacher/cubits/appConfigurationCubit.dart';
import 'package:eschool_teacher/utils/assets.dart';
import 'package:eschool_teacher/utils/labelKeys.dart';
import 'package:eschool_teacher/utils/uiUtils.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_svg/svg.dart';

class ChatMessageSendingWidget extends StatelessWidget {
  const ChatMessageSendingWidget({
    required this.onMessageSend, required this.textController, required this.backgroundColor, required this.onAttachmentTap, super.key,
  });
  final Function() onMessageSend;
  final Function() onAttachmentTap;
  final TextEditingController textController;
  final Color backgroundColor;

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(25),
        color: backgroundColor,
      ),
      padding: const EdgeInsets.symmetric(horizontal: 20),
      child: Row(
        children: [
          GestureDetector(
            onTap: onAttachmentTap,
            child: SvgPicture.asset(
              Assets.attachment,
              width: 20,
              height: 20,
              colorFilter: const ColorFilter.mode(
                Colors.black,
                BlendMode.srcIn,
              ),
            ),
          ),
          Expanded(
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 10),
              child: TextField(
                controller: textController,
                maxLines: 5,
                minLines: 1,
                maxLength: context
                    .read<AppConfigurationCubit>()
                    .getAppConfiguration()
                    .chatSettings
                    .maxCharactersInATextMessage,
                decoration: InputDecoration(
                  border: InputBorder.none,
                  hintText: UiUtils.getTranslatedLabel(
                    context,
                    chatSendHintKey,
                  ),
                  counterText: '',
                  hintStyle: const TextStyle(color: Colors.black, fontSize: 14),
                ),
                onSubmitted: (value) {
                  onMessageSend();
                },
                cursorColor: Colors.black,
                style: const TextStyle(color: Colors.black, fontSize: 14),
              ),
            ),
          ),
          GestureDetector(
            onTap: onMessageSend,
            child: SvgPicture.asset(
              Assets.msgSendIcon,
              width: 20,
              height: 20,
              colorFilter: const ColorFilter.mode(
                Colors.black,
                BlendMode.srcIn,
              ),
            ),
          ),
        ],
      ),
    );
  }
}
