import type { PropsWithChildren } from 'react';
import React, { createContext, useContext, useEffect, useState } from 'react';
import { createPortal } from 'react-dom';
import type { GenericValue } from '@ff-client/types/properties';
import classes from '@ff-client/utils/classes';
import { cloneDeep } from 'lodash';

import { Modal } from './modal';
import { useAnimateModals, useAnimateOverlay } from './modal.animations';
import { ModalHub, ModalOverlay } from './modal.styles';
import type { ModalType } from './modal.types';

type ContextType = {
  openModal: (modal: ModalType, modalData?: GenericValue) => void;
  closeModal: () => void;
};

const ModalContext = createContext<ContextType>({
  openModal: () => void {},
  closeModal: () => void {},
});

export const useModal = (): ContextType => useContext(ModalContext);

export const ModalProvider: React.FC<PropsWithChildren> = ({ children }) => {
  const [data, setData] = useState<GenericValue[]>([]);
  const [modals, setModals] = useState<ModalType[]>([]);

  const openModal = (modal: ModalType, modalData?: GenericValue): void => {
    setData([...data, modalData]);
    setModals([...modals, modal]);
  };

  const closeModal = (): void => {
    setData(data.slice(0, -1));
    setModals(modals.slice(0, -1));
  };

  useEffect(() => {
    if (modals.length > 0) {
      document.body.style.overflow = 'hidden';
    } else {
      document.body.style.overflow = 'auto';
    }
  }, [modals]);

  const overlayAnimation = useAnimateOverlay(modals.length > 0);
  const transitions = useAnimateModals(modals);

  return (
    <ModalContext.Provider value={{ openModal, closeModal }}>
      {children}
      {createPortal(
        <ModalHub>
          <ModalOverlay
            style={overlayAnimation}
            className={classes(!modals.length && 'inactive')}
          >
            {transitions((style, ModalContent, _, index) => (
              <Modal key={index} closeModal={closeModal} style={style}>
                <ModalContent
                  closeModal={closeModal}
                  data={cloneDeep(data[index])}
                />
              </Modal>
            ))}
          </ModalOverlay>
        </ModalHub>,
        document.body
      )}
      {}
    </ModalContext.Provider>
  );
};
