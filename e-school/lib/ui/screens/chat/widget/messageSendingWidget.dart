import 'package:eschool/cubits/appConfigurationCubit.dart';
import 'package:eschool/utils/assets.dart';
import 'package:eschool/utils/labelKeys.dart';
import 'package:eschool/utils/uiUtils.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_svg/svg.dart';

class ChatMessageSendingWidget extends StatelessWidget {
  const ChatMessageSendingWidget({
    required this.onMessageSend,
    required this.textController,
    required this.backgroundColor,
    required this.onAttachmentTap,
    super.key,
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
              colorFilter: ColorFilter.mode(
                Theme.of(context).colorScheme.onSurface,
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
                    .getChatSettings()
                    .maxCharactersInATextMessage,
                decoration: InputDecoration(
                  border: InputBorder.none,
                  hintText: UiUtils.getTranslatedLabel(
                    context,
                    chatSendHintKey,
                  ),
                  counterText: '',
                  hintStyle: TextStyle(
                    color: Theme.of(context).colorScheme.onSurfaceVariant,
                    fontSize: 14,
                  ),
                ),
                onSubmitted: (value) {
                  onMessageSend();
                },
                cursorColor: Theme.of(context).colorScheme.onSurface,
                style: TextStyle(
                  color: Theme.of(context).colorScheme.onSurface,
                  fontSize: 14,
                ),
              ),
            ),
          ),
          GestureDetector(
            onTap: onMessageSend,
            child: SvgPicture.asset(
              Assets.messageSendIcon,
              width: 20,
              height: 20,
              colorFilter: ColorFilter.mode(
                Theme.of(context).colorScheme.onSurface,
                BlendMode.srcIn,
              ),
            ),
          ),
        ],
      ),
    );
  }
}
